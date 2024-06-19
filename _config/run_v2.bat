@echo off
setlocal
rem Check Git installation
where git>nul 2>&1
IF ERRORLEVEL 1 (echo Git not installed. Install Git and re-run this script. pause exit /b 1)
rem Create directory
if not exist "C:\peviitor" (mkdir C:\peviitor)
rem Clone repositories from GitHub
PowerShell -Command "git clone 'https://github.com/peviitor-ro/solr.git' 'C:\peviitor\solr'"
PowerShell -Command "git clone 'https://github.com/peviitor-ro/api.git' 'C:\peviitor\api'"
rem Start Docker Desktop
start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
timeout /T 10
rem Stop and remove existing docker containers 
docker ps -a --format "{{.Names}}" | findstr /C:"apache-container">nul 2>&1 && (docker stop apache-container && docker rm apache-container)
docker ps -a --format "{{.Names}}" | findstr /C:"solr-container">nul 2>&1 && (docker stop solr-container && docker rm solr-container)
docker ps -a --format "{{.Names}}" | findstr /C:"data-migration">nul 2>&1 && (docker stop data-migration && docker rm data-migration)
rem Create Docker Network if not existing 
docker network ls --format "{{.Name}}" | findstr /C:"mynetwork">nul 2>&1 || (docker network create --subnet=172.18.0.0/16 mynetwork) 
rem Running docker containers
docker run --name apache-container --network mynetwork --ip 172.18.0.11 -d -p 8080:80 -v C:/peviitor:/var/www/html sebiboga/php-apache:1.0.0
timeout /T 10
docker run --name solr-container --network mynetwork --ip 172.18.0.10 -d -p 8983:8983 -v "C:\peviitor\solr\core\data:/var/solr/data" sebiboga/peviitor:1.0.0
:loop
echo Waiting for solr container to be ready...
docker exec solr-container nc -w 5 -z localhost 8983>nul 2>&1
IF ERRORLEVEL 1 (echo Solr server not ready, waiting for 30 seconds before retry... TIMEOUT /T 30 GOTO loop)
rem Run data-migration and removing Docker Image
docker run --name data-migration --network mynetwork --ip 172.18.0.12 --rm sebiboga/peviitor-data-migration-local:latest
docker rmi sebiboga/peviitor-data-migration-local:latest
rem Starting Google Chrome with specific urls
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" "http://localhost:8080/api/v0/random"
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" "http://localhost:8983/solr/#/jobs/query"
ENDLOCAL
echo The execution of this script is now completed.
pause