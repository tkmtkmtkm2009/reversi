#coding: UTF-8
import os
import sys
import numpy as np
import tensorflow as tf
from sklearn import datasets
from sklearn.model_selection import train_test_split
from sklearn.utils import shuffle
from flask import Flask, jsonify, render_template, request

'''
モデルファイル用設定
'''
MODEL_DIR = os.path.join(os.path.dirname(__file__), 'reversi')

if os.path.exists(MODEL_DIR) is False:
    os.mkdir(MODEL_DIR)


def inference(x, keep_prob, n_in, n_hiddens, n_out):
    def weight_variable(shape, name=None):
        # initial = np.sqrt(2.0 / shape[0]) * tf.truncated_normal(shape)
        initial = tf.truncated_normal(shape, stddev=0.01)
        return tf.Variable(initial, name=name)

    def bias_variable(shape, name=None):
        initial = tf.zeros(shape)
        return tf.Variable(initial, name=name)

    # 入力層 - 隠れ層、隠れ層 - 隠れ層
    for i, n_hidden in enumerate(n_hiddens):
        if i == 0:
            input = x
            input_dim = n_in
        else:
            input = output
            input_dim = n_hiddens[i-1]

        W = weight_variable([input_dim, n_hidden],
                            name='W_{}'.format(i))
        b = bias_variable([n_hidden],
                          name='b_{}'.format(i))

        h = tf.nn.relu(tf.matmul(input, W) + b)
        output = tf.nn.dropout(h, keep_prob)

    # 隠れ層 - 出力層
    W_out = weight_variable([n_hiddens[-1], n_out], name='W_out')
    b_out = bias_variable([n_out], name='b_out')
    y = tf.nn.softmax(tf.matmul(output, W_out) + b_out)
    return y

def loss(y, t):
    cross_entropy = \
        tf.reduce_mean(-tf.reduce_sum(
                       t * tf.log(tf.clip_by_value(y, 1e-10, 1.0)),
                       reduction_indices=[1]))
    return cross_entropy


def training(loss):
    optimizer = tf.train.AdamOptimizer(learning_rate=0.001,
                                       beta1=0.9,
                                       beta2=0.999)
    train_step = optimizer.minimize(loss)
    return train_step


def accuracy(y, t):
    correct_prediction = tf.equal(tf.argmax(y, 1), tf.argmax(t, 1))
    accuracy = tf.reduce_mean(tf.cast(correct_prediction, tf.float32))
    return accuracy


class EarlyStopping():
    def __init__(self, patience=0, verbose=0):
        self._step = 0
        self._loss = float('inf')
        self.patience = patience
        self.verbose = verbose

    def validate(self, loss):
        if self._loss < loss:
            self._step += 1
            if self._step > self.patience:
                if self.verbose:
                    print('early stopping')
                return True
        else:
            self._step = 0
            self._loss = loss

        return False


mat = np.loadtxt("../tmp/data/test.csv", skiprows=0, delimiter=",")

n = len(mat) # 41247
N = 40000  # 一部を使う
N_train = 35000
N_validation = 5000
indices = np.random.permutation(range(n))[:N]  # ランダムにN枚を選択

X = mat[indices, :-2]
Y = mat[indices, -2:]

X_train, X_test, Y_train, Y_test = \
    train_test_split(X, Y, train_size=N_train)

X_train, X_validation, Y_train, Y_validation = \
    train_test_split(X_train, Y_train, test_size=N_validation)

'''
モデル設定
'''
n_in = len(X[0])
n_hiddens = [200, 200, 200]  # 各隠れ層の次元数
n_out = len(Y[0])
p_keep = 0.5

x = tf.placeholder(tf.float32, shape=[None, n_in])
t = tf.placeholder(tf.float32, shape=[None, n_out])
keep_prob = tf.placeholder(tf.float32)

y = inference(x, keep_prob, n_in=n_in, n_hiddens=n_hiddens, n_out=n_out)
loss = loss(y, t)
train_step = training(loss)

accuracy = accuracy(y, t)
early_stopping = EarlyStopping(patience=10, verbose=1)

history = {
    'val_loss': [],
    'val_acc': []
}

saver = tf.train.Saver()  # モデル読み込み用
sess = tf.Session()
# sess.run(init)

# 中断して保存したモデルを読み込み
saver.restore(sess, MODEL_DIR + '/model_100.ckpt')


# webapp
app = Flask(__name__)

@app.route('/api/reversi', methods=['GET'])
def reversi():
    tmp_board = request.args.get('board', '')
    tmp_board = list(tmp_board)
    board = np.zeros(128, dtype=np.int32)
    for i in range(64):
        if int(tmp_board[i]) == 1:
            board[i] = 1

    for i in range(64):
        if int(tmp_board[i]) == 2:
            board[64 + i] = 1
    board_copy = np.array([board])

    y_rate = sess.run(y, feed_dict={x: board_copy, keep_prob: 1.0}).flatten().tolist()
    return jsonify(results=y_rate)


@app.route('/')
def main():
    return render_template('index.html')


if __name__ == '__main__':
    app.run(host="0.0.0.0", port=8889, debug=False)