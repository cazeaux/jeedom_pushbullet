__author__ = 'Igor Maculan <n3wtron@gmail.com>'

import logging
import threading
import os
import sys
import signal
import json
import subprocess
import time
from threading import Timer
from pushbullet import Listener

logging.basicConfig(level=logging.ERROR)

HTTP_PROXY_HOST = None
HTTP_PROXY_PORT = None


class Command(object):
    global thread

    def __init__(self, api_key):
        self.api_key = api_key
        self.s = None

    def run(self, timeout):
        def target():
            program_path = os.path.dirname(os.path.realpath(__file__))
            logger.debug("Thread started, timeout = " + str(timeout))
            self.process = subprocess.Popen(f"/usr/bin/php {program_path}/../../core/php/newpush.php {self.api_key}", shell=True)
            self.process.communicate()
            logger.debug("Return code: " + str(self.process.returncode))
            logger.debug("Thread finished")
            self.timer.cancel()

        def timer_callback():
            logger.debug("Thread timeout, terminate it")

        thread = threading.Thread(target=target)
        self.timer = threading.Timer(int(timeout), timer_callback)
        self.timer.start()
        thread.start()


# ----------------------------------------------------------------------------
# DAEMONIZE
# Credit: George Henze
# ----------------------------------------------------------------------------

def shutdown():
    logger.debug("Shutdown")

    logger.debug(f"Removing PID file {str(pidfile)}")
    os.remove(pidfile)

    logger.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)


def handler(signum=None, frame=None):
    logger.debug(f"Signal {int(signum)} caught, exiting...")
    shutdown()


def daemonize():
    try:
        pid = os.fork()
        if pid != 0:
            sys.exit(0)
    except OSError as e:
        raise RuntimeError(f"1st fork failed: {e.strerror} [{e.errno}]")

    os.setsid()

    prev = os.umask(0)
    os.umask(prev and int('077', 8))

    try:
        pid = os.fork()
        if pid != 0:
            sys.exit(0)
    except OSError as e:
        raise RuntimeError(f"2nd fork failed: {e.strerror} [{e.errno}]")

    dev_null = open('/dev/null', 'r')
    os.dup2(dev_null.fileno(), sys.stdin.fileno())

    pid = str(os.getpid())
    logger.debug(f"Writing PID {pid} to {str(pidfile)}")
    #with open(pidfile, 'w') as f:
    #    f.write(f"{pid}\n")
    f = open(pidfile, "w")
    f.write("%s\n" % pid)
    f.close()


def logger_init(name, debug):
    program_path = os.path.dirname(os.path.realpath(__file__))
    logfile = '/tmp/pushbullet.log'
    loglevel = 'ERROR'

    formatter = logging.Formatter('%(asctime)s - %(threadName)s - %(module)s:%(lineno)d - %(levelname)s - %(message)s')

    handler = logging.FileHandler(logfile) if not debug else logging.StreamHandler()

    handler.setFormatter(formatter)

    logger = logging.getLogger(name)
    logger.setLevel(logging.getLevelName(loglevel))
    logger.addHandler(handler)

    return logger


def on_push(push):
    program_path = os.path.dirname(os.path.realpath(__file__))
    process = subprocess.Popen(f"/usr/bin/php {program_path}/../../core/php/newpush.php {API_KEY}", shell=True)
    process.communicate()
    logger.debug(f"Return code: {process.returncode}")
    logger.debug("Thread finished")


def on_ping(push):
    global is_alive
    is_alive = 1


def main():
    global logger
    global pidfile
    global API_KEY
    global is_alive

    is_alive = 0

    API_KEY = sys.argv[1]
    logger = logger_init('pushbullet', True)

    logger.debug("Check PID file")

    path = os.path.join(os.path.dirname(os.path.realpath(__file__)), '../../../../tmp')

    try:
        os.makedirs(path)
    except OSError:
        if not os.path.isdir(path):
            raise

    pidfile = f'{path}/pushbullet.{API_KEY}.pid'

    logger.debug(f"PID file '{pidfile}'")

    if os.path.exists(pidfile):
        print(f"PID file '{pidfile}' already exists. Exiting.")
        logger.debug(f"PID file '{pidfile}' already exists.")
        logger.debug("Exit 1")
        sys.exit(1)

    try:
        logger.debug("Write PID file")
        with open(pidfile, 'w') as f:
            f.write("pid\n")
    except IOError as e:
        logger.error(f"Unable to write PID file: {e.strerror} [{e.errno}]")
        raise SystemExit(f"Unable to write PID file: {e.strerror} [{e.errno}]")

    logger.debug("Start daemon")
    daemonize()

    s = Listener(api_key=API_KEY,
                 on_push=on_push,
                 http_proxy_host=HTTP_PROXY_HOST,
                 http_proxy_port=HTTP_PROXY_PORT)

    s.run_forever()

    t = Timer(60, timeout)
    t.start()

    sys.exit(0)


def timeout(push):
    global is_alive
    if is_alive == 0:
        sys.exit(0)

    is_alive = 0

    t = Timer(60, timeout)
    t.start()


if __name__ == '__main__':
    signal.signal(signal.SIGTERM, handler)
    signal.signal(signal.SIGINT, handler)

    main()
