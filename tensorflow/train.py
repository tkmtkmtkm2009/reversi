#coding: UTF-8
import sys
import os
import shutil
import numpy as np
import tensorflow as tf
from sklearn import datasets
from sklearn.model_selection import train_test_split
from sklearn.utils import shuffle
# import matplotlib.pyplot as plt

np.random.seed(0)
tf.set_random_seed(1234)

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


if __name__ == '__main__':
    '''
    データの生成
    '''
    mat = np.loadtxt("../tmp/data/test.csv", skiprows=0, delimiter=",")

    n = len(mat) # 41247
    N = 40000  # 一部を使う
    N_train = 35000
    N_validation = 5000
    indices = np.random.permutation(range(n))[:N]  # ランダムにN枚を選択

    # ind_train = np.random.choice(n, N_train, replace=False)
    # ind_test = np.array([i for i in range(n) if i not in ind_train])

    # X_train = mat[ind_train, :-64]
    # X_test = mat[ind_test, :-64]

    # Y_train = mat[ind_train, -64:]
    # Y_test = mat[ind_test, -64:]

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

    '''
    モデル学習
    '''
    epochs = 200
    batch_size = 200

    init = tf.global_variables_initializer()
    saver = tf.train.Saver()
    sess = tf.Session()
    sess.run(init)

    n_batches = N_train // batch_size

    for epoch in range(epochs):
        X_, Y_ = shuffle(X_train, Y_train)

        for i in range(n_batches):
            start = i * batch_size
            end = start + batch_size

            sess.run(train_step, feed_dict={
                x: X_[start:end],
                t: Y_[start:end],
                keep_prob: p_keep
            })

        # 検証データを用いた評価
        val_loss = loss.eval(session=sess, feed_dict={
            x: X_validation,
            t: Y_validation,
            keep_prob: 1.0
        })
        val_acc = accuracy.eval(session=sess, feed_dict={
            x: X_validation,
            t: Y_validation,
            keep_prob: 1.0
        })

        # 検証データに対する学習の進み具合を記録
        history['val_loss'].append(val_loss)
        history['val_acc'].append(val_acc)

        print('epoch:', epoch,
              ' validation loss:', val_loss,
              ' validation accuracy:', val_acc)

        model_path = \
            saver.save(sess, MODEL_DIR + '/model_{}.ckpt'.format(epoch))
        print('Model saved to:', model_path)

        # Early Stopping チェック
        if early_stopping.validate(val_loss):
            break

    '''
    学習の進み具合を可視化
    plt.rc('font', family='serif')
    fig = plt.figure()
    plt.plot(range(len(history['val_loss'])), history['val_loss'],
             label='loss', color='black')
    plt.xlabel('epochs')
    plt.show()
    '''

    '''
    予測精度の評価
    '''
    accuracy_rate = accuracy.eval(session=sess, feed_dict={
        x: X_test,
        t: Y_test,
        keep_prob: 1.0
    })
    print('accuracy: ', accuracy_rate)
