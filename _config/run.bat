docker network create --subnet=172.18.0.0/16 mynetwork
docker run --name apache-container --network mynetwork --ip 172.18.0.11 -d -p 8080:80 -v C:/php:/var/www/html sebiboga/php-apache:1.0.0
docker run --name solr-container --network mynetwork --ip 172.18.0.10 -d -p 8983:8983 -v c:\solrdata\solr\core:/var/solr sebiboga/peviitor:1.0.0
