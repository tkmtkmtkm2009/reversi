docker ローカル環境作成 2017/05/19

以下ページ参考に作成(見なくて可)
【超簡単】Docker でモダンな PHP 開発環境を作る (PHP, MySQL, PHP-FPM, nginx, memcached)
http://koni.hateblo.jp/entry/2017/01/28/150522

image 一覧
docker images

docker rmi 976dd870f876
docker rmi 3448f27c273f

コンテナへのアクセス方法(ssh)
docker exec -it 62311f81905b bash

docker-compose build

docker コンテナ全て削除方法(とくに何もなければ不必要)
docker-compose down --rmi all

mysql のデータは以下に作成
\docker\misc
コンテナ全て削除しても misc フォルダがあれば、データは残ります
misc 削除で、mysql データ削除されます


DBデータ 開発->local にコピー
テーブル構造が違うとエラー

\ubuntu\script\dbCheckList.list 編集

docker exec -it 344c989a5057 bash
cd /script/
./db_sync_check.sh
mysql -hmysql -uroot -proot_passward < temp.sql



///////////////////

CONTAINER ID        IMAGE                   COMMAND                  CREATED             STATUS              PORTS                                        NAMES
3c6373b32251        docker_nginx            "nginx -g 'daemon ..."   3 days ago          Up 3 days           0.0.0.0:443->443/tcp, 0.0.0.0:8000->80/tcp   nginx
1ef5b9be51c5        docker_web              "docker-php-entryp..."   3 days ago          Up 3 days           9000/tcp                                     web
ff7e303e6e7e        docker_ubuntu           "/bin/bash"              3 weeks ago         Up 3 days                                                        ubuntu
6d09e4a19d0c        phpmyadmin/phpmyadmin   "/run.sh phpmyadmin"     3 weeks ago         Up 3 days           0.0.0.0:8080->80/tcp                         docker_phpmyadmin_1
602e569dfb5d        mysql                   "docker-entrypoint..."   3 weeks ago         Up 3 days           0.0.0.0:13306->3306/tcp                      mysql
43873f9f4124        memcached               "docker-entrypoint..."   3 weeks ago         Up 3 days           11211/tcp                                    memcached
0d9f5d2b86e3        redis                   "docker-entrypoint..."   3 weeks ago         Up 3 days           6379/tcp                                     redis

web
docker exec -it 1ef5b9be51c5 bash

mysql
docker exec -it 602e569dfb5d bash

docker inspect --format '{{ .NetworkSettings.IPAddress }}' 602e569dfb5d


docker logs 24347f3883b3

docker logs 602e569dfb5d


マイグレーション生成(SQL生成)
php artisan make:migration reversi_logs_table
php artisan make:migration reversi_user_results_table
php artisan make:migration reversi_user_statuses_table

webサーバで以下実行
cd project-name
php artisan migrate


モデル作成
php artisan make:model ReversiLog
php artisan make:model ReversiUserResult
php artisan make:model ReversiUserStatus


コントローラー作成
php artisan make:controller ReversiController

キャッシュクリア
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear


