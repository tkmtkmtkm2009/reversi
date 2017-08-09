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
8df1c57a26bd        docker_nginx            "nginx -g 'daemon ..."   10 seconds ago      Up 7 seconds        0.0.0.0:443->443/tcp, 0.0.0.0:8000->80/tcp   nginx
48782715a134        docker_web              "docker-php-entryp..."   14 seconds ago      Up 10 seconds       9000/tcp                                     web
e43ef6256b06        docker_tensorflow       "/run_jupyter.sh -..."   17 seconds ago      Up 15 seconds       6006/tcp, 0.0.0.0:8888-8889->8888-8889/tcp   docker_tensorflow_1
1a2f18c92635        docker_ubuntu           "/bin/bash"              11 days ago         Up 26 minutes                                                    ubuntu
cebd760d5648        phpmyadmin/phpmyadmin   "/run.sh phpmyadmin"     11 days ago         Up 26 minutes       0.0.0.0:8080->80/tcp                         docker_phpmyadmin_1
26ce39350c6c        mysql                   "docker-entrypoint..."   11 days ago         Up 26 minutes       0.0.0.0:13306->3306/tcp                      mysql
70582e8b05ce        memcached               "docker-entrypoint..."   11 days ago         Up 26 minutes       11211/tcp                                    memcached
3a3ae4e39a49        redis                   "docker-entrypoint..."   11 days ago         Up 26 minutes       6379/tcp                                     redis


web
docker exec -it 1ef5b9be51c5 bash

mysql
docker exec -it 602e569dfb5d bash

tensorflow
docker exec -it e43ef6256b06 bash

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
php artisan make:controller Wtb2CsvGenerateController

キャッシュクリア
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear


tensorflow
http://localhost:8888/?token=a18b6520367cf1607009c866245c78cc61f33a24fbdd87cf



import sys
import numpy as np
import tensorflow as tf
from sklearn import datasets
from sklearn.model_selection import train_test_split
from sklearn.utils import shuffle
import matplotlib.pyplot as plt

%matplotlib inline

np.random.seed(0)
tf.set_random_seed(1234)

'''
データ生成
'''
N = 300  # 全データ数
X, y = datasets.make_moons(N, noise=0.3)
Y = y.reshape(N, 1)

# Show the fit and the loss over time.
fig, (ax1) = plt.subplots(1, 1)
plt.subplots_adjust(wspace=.3)
fig.set_size_inches(5, 4)
for k,v in enumerate(X):
    if Y[k][0] == 0:
        ax1.scatter(v[0], v[1], alpha=.7)
plt.show()
