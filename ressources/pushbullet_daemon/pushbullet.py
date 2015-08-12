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
	global thead
	def __init__(self, api_key):
		self.api_key = api_key
		self.s = None
	
	def run(self, timeout):
		def target():
			program_path = os.path.dirname(os.path.realpath(__file__))
			logger.debug("Thread started, timeout = " + str(timeout))
			self.process = subprocess.Popen("/usr/bin/php "+program_path+"/../../core/php/newpush.php "+self.api_key, shell=True)
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
# DEAMONIZE
# Credit: George Henze
# ----------------------------------------------------------------------------

def shutdown():
	# clean up PID file after us
	logger.debug("Shutdown")

	logger.debug("Removing PID file " + str(pidfile))
	os.remove(pidfile)

	logger.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)
	
def handler(signum=None, frame=None):
	logger.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()


def daemonize():

	try:
		pid = os.fork()
		if pid != 0:
			sys.exit(0)
	except OSError, e:
		raise RuntimeError("1st fork failed: %s [%d]" % (e.strerror, e.errno))

	os.setsid() 

	prev = os.umask(0)
	os.umask(prev and int('077', 8))

	try:
		pid = os.fork() 
		if pid != 0:
			sys.exit(0)
	except OSError, e:
		raise RuntimeError("2nd fork failed: %s [%d]" % (e.strerror, e.errno))

	dev_null = file('/dev/null', 'r')
	os.dup2(dev_null.fileno(), sys.stdin.fileno())

	pid = str(os.getpid())
	logger.debug("Writing PID " + pid + " to " + str(pidfile))
	file(pidfile, 'w').write("%s\n" % pid)

def logger_init(name, debug):
	program_path = os.path.dirname(os.path.realpath(__file__))
	logfile = '/tmp/pushbullet.log'
	loglevel = 'ERROR'

	#formatter = logging.Formatter(fmt='%(asctime)s - %(levelname)s - %(module)s - %(message)s')
	formatter = logging.Formatter('%(asctime)s - %(threadName)s - %(module)s:%(lineno)d - %(levelname)s - %(message)s')
	
	if debug:
		loglevel = "DEBUG"
		handler = logging.StreamHandler()
	else:
		handler = logging.FileHandler(logfile)
					
	handler.setFormatter(formatter)
	
	logger = logging.getLogger(name)
	logger.setLevel(logging.getLevelName(loglevel))
	logger.addHandler(handler)
	
	return logger

def on_push(push):
	# mode synchrone
	program_path = os.path.dirname(os.path.realpath(__file__))
	process = subprocess.Popen("/usr/bin/php "+program_path+"/../../core/php/newpush.php "+API_KEY, shell=True)
	process.communicate()
	logger.debug("Return code: " + str(process.returncode))
	logger.debug("Thread finished")
	
	#mode multi-thread
	#command = Command(API_KEY)
	#command.run(1000)

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
	logger = logger_init('pushbullet', False)
	
	logger.debug("Check PID file")
	
	pidfile = os.path.dirname(os.path.realpath(__file__))+'/../../../../tmp/pushbullet.'+API_KEY+'.pid'
	createpid = True

	logger.debug("PID file '" + pidfile + "'")

	if os.path.exists(pidfile):
		print("PID file '" + pidfile + "' already exists. Exiting.")
		logger.debug("PID file '" + pidfile + "' already exists.")
		logger.debug("Exit 1")
		sys.exit(1)

	try:
		logger.debug("Write PID file")
		file(pidfile, 'w').write("pid\n")
	except IOError, e:
		#logger.error("Line: " + _line())
		logger.error("Unable to write PID file: %s [%d]" % (e.strerror, e.errno))
		raise SystemExit("Unable to write PID file: %s [%d]" % (e.strerror, e.errno))

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

	# Init shutdown handler
	signal.signal(signal.SIGTERM, handler)
	signal.signal(signal.SIGINT, handler)

	main()
