import os
import sys

from .setuptools import setup

# Lire la version du fichier __version__.py
with open("./pushbullet/__version__.py") as version_file:
    version = version_file.read().split("\"")[1]

# Si le dernier argument de la ligne de commande est 'publish', publier le package
if sys.argv[-1] == 'publish':
    os.system('python setup.py sdist upload')
    sys.exit()

# Fonction pour lire un fichier
def read(fname):
    try:
        with open(os.path.join(os.path.dirname(__file__), fname)) as f:
            return f.read()
    except IOError:
        return ""

# Configuration du package
setup(
    name="pushbullet.py",
    version=version,
    author="Richard Borcsik",
    author_email="borcsikrichard@gmail.com",
    description="A simple python client for pushbullet.com",
    license="MIT",
    keywords="push android pushbullet notification",
    url="https://github.com/randomchars/pushbullet.py",
    download_url="https://github.com/randomchars/pushbullet.py/tarball/" + version,
    packages=['pushbullet'],
    long_description=read('readme.md'),
    classifiers=[
        "Development Status :: 4 - Beta",
        "Intended Audience :: Developers",
        "Programming Language :: Python",
        "Natural Language :: English",
        "License :: OSI Approved :: MIT License",
        "Programming Language :: Python :: 2.7",
        "Programming Language :: Python :: 3",
        "Programming Language :: Python :: 3.2",
        "Topic :: Software Development :: Libraries :: Python Modules",
        "Topic :: Utilities"
    ],
    install_requires=[
        'requests>=1.0.0',
        'python-magic',
        'websocket-client',
    ]
)
