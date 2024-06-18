@echo off
setlocal

set "repo_url1=https://github.com/peviitor-ro/solr.git"
set "repo_url2=https://github.com/peviitor-ro/api.git"

rem Check if Git is installed
where git >nul 2>&1
IF ERRORLEVEL 1 (
    echo Git is not installed. Please install Git and re-run the script.
    pause
    exit /b 1
)

rem Check if folder exists, if not create it
if not exist "C:\peviitor" (
    mkdir C:\peviitor
)

PowerShell -Command "git clone '%repo_url1%' 'C:\peviitor\solr'"
PowerShell -Command "git clone '%repo_url2%' 'C:\peviitor\api'"

rem Attempt to start Docker Desktop
start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
timeout /T 10

SET NetworkName=mynetwork
SET ApacheContainerName=apache-container
SET SolrContainerName=solr-container
SET DataMigrationContainerName=data-migration
SET DataMigrationImageName=sebiboga/peviitor-data-migration-local:latest 

REM Remove existing containers if they exist
FOR %%C IN ("%ApacheContainerName%" "%SolrContainerName%" "%DataMigrationContainerName%") DO (
    docker ps -a --format "{{.Names}}" | findstr /C:%%~C >nul 2>&1 && (
        docker stop %%~C
        docker rm %%~C
    )
)

REM Check if the network exists; if not then create it
docker network ls --format "{{.Name}}" | findstr /C:"%NetworkName%" >nul 2>&1 || (docker network create --subnet=172.18.0.0/16 %NetworkName%)

REM Run the containers
docker run --name %ApacheContainerName% --network %NetworkName% --ip 172.18.0.11 -d -p 8080:80 -v C:/peviitor:/var/www/html sebiboga/php-apache:1.0.0
timeout /T 10

docker run --name %SolrContainerName% --network %NetworkName% --ip 172.18.0.10 -d -p 8983:8983 -v "C:\peviitor\solr\core\data:/var/solr/data" sebiboga/peviitor:1.0.0

:loop
echo Waiting for solr container to be ready...
docker exec %SolrContainerName% nc -w 5 -z localhost 8983 >nul 2>&1
IF ERRORLEVEL 1 (
   echo Solr server not ready, waiting for 30 seconds before retry...
   TIMEOUT /T 30
   GOTO loop
)

docker run --name %DataMigrationContainerName% --network %NetworkName% --ip 172.18.0.12 --rm sebiboga/peviitor-data-migration-local:latest

REM Removing the data migration image
docker rmi %DataMigrationImageName%

REM Start Google Chrome
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" "http://localhost:8080/api/v0/random" 
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" "http://localhost:8983/solr/#/jobs/query"
   

ENDLOCAL
echo This script execution is completed.
pause