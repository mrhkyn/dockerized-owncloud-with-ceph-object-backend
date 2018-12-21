# dockerized-owncloud-with-ceph-object-backend
This is a project for deploying a kind of document server where users can upload/download and edit their documents online. All the components required for the project are containers. The network and volumes of the containers were configured in their own space in order to migrate the application from one physichal host to the another one in a easy and flexible way.

![alt text](screenshots/owncloud-1.png?raw=true "Owncloud Server")

Owncloud is an open-source platform and it has plenty amount of documents available showing how to install it using docker images. It has several basic components such as owncloud application, nginx reverse proxy, redis cache, and mariadb database server where a single file (docker-compose.yaml) is required to configure and install all containers. However, this is not enough to allow users editing their documents online so that a new container, onlyoffice, is required to be installed and configured properly running with owncloud together. Additionally, securing http traffic with SSL is a neccessity so that client can interact with owncloud and onlyoffice server in a secure way. Finally, it might be required to store the files at the our own CEPH backend storage as objects rather than on local disks. Therefore, an authorization and a communication mechanisms are required. In this document, the information about installation and deployment for a full-stack document server having all the mentioned functionalities are covered with the configuration files.

# Multi-container for an application - docker-compose.yaml

There are 5 containers configured in docker-compose.yaml running on their internal network and having own volumes. The list of containers are given as following.

![alt text](screenshots/console.png?raw=true "List of docker containers")

## The list of containers

* owncloud
  * IP: 172.16.46.2
  * Volume: It has a single volume where external applications (./apps), configurations (./config), root certifications (./certs), sessions (./sessions) and user individual files (./files) have been stored. For example, onlyoffice is one of the application where documents can be opened and edited online. 
Port: It is not required to expose the default ports (80 and 443) that owncloud running on because nginx is configured as reverse proxy so that it should not be opened directly to the world to avoid potential security risks.
  * Environment: The configuration of database such as username, password, database name as well as owncloud such as administrator password are set here. 

* database - mariadb
  * IP: 172.16.46.3
  * Volume: mysql and backup are the volumes that the configuration files of db and the back-up of the databases are stored here. There is no additional requirement for database since the environment section is enough for owncloud application server.
  * Environment: The database properties given in owncloud should be the same. Also, the basic optimization can be set here.

* redis
  * IP: 172.16.46.4

* nginx:
  * IP: 172.16.46.5
  * PORTS: 443, 80 These are the ports opened the external networks. Therefore, the firewall rules should be properly applied if ip based restriction would be applied. 
  * VOLUMES: The nginx configuration files

* onlyoffice-document-server
  * IP: 172.16.46.6
  * PORT: 8080 was opened and re-directed to the https port 443 where onlyoffice is running. Firewall rules should be applied similarly.
  * Volumes: The root certification and the configuration files should be stored here.

# Network Setup and Root CA-Certificate

A new separated network should be created in order that all these containers running on their own network. A gigabit interface has been configured in that respect. Furthermore, a public ip is given to another interface and registered to DNS server where the exposed ports (80, 8080, 443) can be accessed with given domain name. (The firewall should be properly configured as well) Additionally, a self-signed or registered root certificates proper to the given domain are also critical to run the application on https port.

If you do not have a root certificate, you can subscribe for a new server certificate with registered domain name to letsencrypt, an automated and open certification authority and obtain a valid certificate for 90 days. The renewal of the certificate can be automatically performed using another docker image and added to the docker compose file which is out of the scope here. The following tutorial is a good example which can be followed if required.

```
https://github.com/evertramos/docker-compose-letsencrypt-nginx-proxy-companion
```

# Onlyoffice - Document Server Installation and Configuration

To integrate Owncloud with online Onlyoffice editors, the integration application should be stored into the volume belong to the Owncloud.(under app folder).

```
 cd apps/
 git clone https://github.com/ONLYOFFICE/onlyoffice-owncloud.git onlyoffice
 chown -R www-data:www-data onlyoffice
```

After installation the application, the administration panel has ONLYOFFICE section where we can set the document editing service address. Since we install the document server on a container running on 8080 port, the address should be https://drive.example.com:8080/ Thus, any documents that user have can be accessed and edited online. 

The main problem might be here is that the communication between owncloud and onlyoffice in a secure way using SSL certificate might be problem. If your certificate is not issued by a certification authority that is not commonly accepted or use of a self-signed certificate rather than letsencrypt, nodejs might not work properly that the document could not be opened by the document server. In that case, the config file (default.json) under the document-conf volume should be edited and rejectUnauthorized should be set false. (services -> requestDefaults->rejectUnauthorized) Additionally, the config file of owncloud server should have following description.

```
 'onlyoffice' => 
   array (
     'verify_peer_off' => true,
 ),
```

The onlyoffice container has nginx reverse proxy and it can be deployed into another container if prefered.

# Nginx Reverse Proxy

The configuration file has been stored under proxy volume. The owncloud is running on HTTP 80 port. However, nginx reverse proxy is listening both HTTP 80 and HTTPS 443 port with SSL secure options. CA root certificates should be located into the path given inside the configuration file. Onlyoffice has its own nginx reverse proxy so that it does not require make any changes for document server. (keep in mind that the document server is running on another port, 8080)

# External Storage - CEPH Object Backend
It's possible to store user documents at the external storage such as CEPH rather than local disk at the owncloud server. CEPH has two interfaces, S3 and Swift where the client can communicate and get services using rados gateway. Owncloud supports both CEPH S3 and OpenStack Swift interfaces. 

In our configuration, we have already installed and managed a CEPH object cluster where rados gateway has been properly configured so that owncloud external storage backend can  communicate. The problem here is that the version of the owncloud might require licence. Therefore, it would be nice to check whether the version has community support for this backend especially looking for the class (for example, OCA\ObjectStore\S3) Owncloud 9.0 image, instead of the latest one, has been used for this purpose and OpenStack keystone service has been utilized for the authentication because it does not currenly support CEPH S3 api natively. As a result, the owncloud can store the user documents on CEPH object storage as if it as SWIFT storage because swift interfaces of rados-gw for client communication and openstack keystone for authentication would be used. The configuration of owncloud via web admin should like be as following:

```
 External Storage: OpenStack Object Storage
 Authentication: OpenStack
 Configuration: swift
 Region: RegionOne
 Container_name: Previously created container for the tenant
 Tenant name, project name and password for the tenant in order to be authorized by keystone api.
 authentication url: the endpoint of the keystone service.
```

Additionally, keystone should have following endpoints because CEPH object pool will be used over Swift API.

```
 openstack endpoint list|grep swift
 | ID | RegionOne | swift        | object-store   | True    | admin     | http://radosgw-address:7480/swift/v1              |
 | ID | RegionOne | swift        | object-store   | True    | internal  | http://radosgw-address:7480/swift/v1              |
 | ID | RegionOne | swift        | object-store   | True    | public    | http://radosgw-address:7480/swift/v1              |
```

If it works, the files will be stored at the container created for the tenant simalarly the object pool located at the CEPH cluster.
