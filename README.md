# new
Run Everything
  - docker-compose up build -d
Then
  - docker-compose exec app composer install
  - docker-compose exec app php artisan key:generate
  - docker-compose exec app php artisan migrate
Visit 
  - http://localhost:8000
To view tables install the DB Browser ( already in the file)
  - DB.Browser.for.SQLite-v3.13.1-win32


To run seeder
  - docker-compose exec app php artisan db:seed
To create tables
  - docker-compose exec app php artisan migrate


Test the reset.sh
Windows (Git Bash)
execute sh
chmod +x scripts/db/reset.sh

run it
bash scripts/
db/reset.sh or ./scripts/db/reset.sh


Verification 
git clone <repo>
cd project
cp .env.example .env
docker compose up -d
bash scripts/db/reset.sh
