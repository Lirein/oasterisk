#!/usr/bin/python
import os # PYSFL
import sys # PYSFL
import hashlib # PYSFL
import socket # PYSFL
import OpenSSL # Apache 2.0  <----- External
import pyudev

from os.path import exists

scriptpath = os.path.abspath(os.path.dirname(sys.argv[0]))
_baseurl = "repo.oas.su"
_stable = "stable"
_testing = "testing"

def create_csr(out_key, host=socket.getfqdn(socket.gethostname())):
    # create a key pair
    k = OpenSSL.crypto.PKey()
    k.generate_key(OpenSSL.crypto.TYPE_RSA, 2048)

    # create a ca-signed cert
    cert = OpenSSL.crypto.X509Req()
    cert.set_version(3)
    cert.get_subject().C = "RU"
    cert.get_subject().ST = "Tyumen"
    cert.get_subject().L = "Tyumen"
    cert.get_subject().O = "Open Automation Systems LLC"
    cert.get_subject().OU = "Source Server"
    cert.get_subject().CN = host
    cert.set_pubkey(k)
    cert.add_extensions([
        OpenSSL.crypto.X509Extension("basicConstraints", True,
                                     "CA:FALSE, pathlen:0"),
        OpenSSL.crypto.X509Extension("keyUsage", True,
                                     "nonRepudiation, digitalSignature, keyEncipherment"),
        OpenSSL.crypto.X509Extension("extendedKeyUsage", True,
                                     "serverAuth, clientAuth"),
    ])

    if out_key[0]=='/':
        file = open(out_key, "wt")
    else:
        file = open(os.path.join(scriptpath, out_key), "wt")
    file.write(OpenSSL.crypto.dump_privatekey(OpenSSL.crypto.FILETYPE_PEM, k))
    return OpenSSL.crypto.dump_certificate_request(OpenSSL.crypto.FILETYPE_PEM, cert)

def writerepo(repo, mode, src):
    if repo!="testing":
        repo="stable"
    modestr = ""
    if 'ubuntu' in mode:
        modestr+=' ubuntu'
        if 'security' in mode:
            modestr+=' ubuntu-security'
        if 'updates' in mode:
            modestr += ' ubuntu-updates'
        if 'backports' in mode:
            modestr += ' ubuntu-backports'
    if 'mint' in mode:
        modestr+=' mint'
        if 'updates' in mode:
            modestr += ' mint-updates'
            if repo == 'testing':
                modestr += ' mint-unstable'
        if 'backports' in mode:
            modestr += ' mint-backports'
    if 'oas' in mode:
        modestr+=' source'
    if 'thirdparty' in mode:
        modestr+=' thirdparty'
    f = open('/etc/apt/sources.list.d/oas-subscription.list', 'w')
    f.write("deb https://%s %s%s\n" % (_baseurl, repo, modestr))
    if src:
        f.write("deb-src https://%s %s%s\n" % (_baseurl, repo, modestr))
    f.close()

def getrootserial():
    context = pyudev.Context()

    def _gethddserial(path):
        device = pyudev.Device.from_device_file(context, path)
        serial = device.get('ID_SERIAL_SHORT')
        isdm = device.get('DM_NAME') != None
        ismd = device.get('MD_NAME') != None
        if (serial == None) and (isdm or ismd):
            path = "/sys" + device.device_path
            dev = os.listdir(os.path.join(path, 'slaves'))[0]
            serial = _gethddserial(os.path.join('/dev', dev))
        return serial
    file = open('/proc/mounts')
    root = "/dev/sda"
    for line in file:
        try:
            data = line.split(' ')
            if data[1] == '/':
                root = data[0]
        finally:
            pass
    file.close()
    return _gethddserial(root)

def request():
    if not exists('/etc/apt/sources.list.d/oas-subscription.list'):
        hddserial = getrootserial()
        csr = create_csr('/etc/source/subscription.key', hddserial)
        return csr
    else:
        return False

def cert(data):
    if not exists('/etc/apt/sources.list.d/oas-subscription.list'):
        f = open('/etc/source/subscription.crt','w')
        f.write(data)
        f.close()
        try:
            writerepo(_stable, ('oas'), False)
            f = open('/etc/apt/apt.conf.d/00oas-subscription', 'w')
            f.write("Acquire::https::%s::SslCert %s;\n" % (_baseurl, '/etc/source/subscription.crt'))
            f.write("Acquire::https::%s::Sslkey %s;\n" % (_baseurl, '/etc/source/subscription.key'))
            f.close()
            try:
                data = ""
                f = open('/etc/apt/sources.list.d/official-package-repositories.list', 'r')
                for line in f:
                    if line.startswith('deb'):
                        data += '#'+line
                    else:
                        data += line
                f.close()
                f = open('/etc/apt/sources.list.d/official-package-repositories.list', 'w')
                f.write(data)
                f.close()
            except:
                pass
            return True
        except:
            pass
    return False

csr = request()
url = "https://cert.oas.su/subscribe?req=%s" % str(csr).replace("\n","%0A").replace(" ","%20").replace("+","%2B")
sys.stdout.write("You need to register this request by your organization or technician certificate.\nCopy or click on this URL with Ctrl key holded: %s\nAfter register this request, please past results here and press ENTER.\n" % url)
certdata = ""
try:
    line = sys.stdin.readline()
except:
    line = "\n"
while (line<>"\n") and (line.find("-----END CERTIFICATE-----")==-1):
    certdata = certdata + line
    try:
        line = sys.stdin.readline()
    except:
        line = "\n"
certdata = certdata + line
result = cert(certdata)
if result == True:
    sys.stdout.write("Success!\n")
else:
    sys.stdout.write("Fail!\n")
sys.stdout.write("\n")
