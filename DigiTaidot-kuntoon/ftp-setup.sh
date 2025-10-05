#!/bin/bash

echo "🔧 FTP-palvelimen nopea setup..."

# Asenna vsftpd
sudo apt update && sudo apt install -y vsftpd

# Backup vanha config
sudo cp /etc/vsftpd.conf /etc/vsftpd.conf.backup

# Luo uusi config
sudo tee /etc/vsftpd.conf > /dev/null << 'EOF'
listen=NO
listen_ipv6=YES
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
dirmessage_enable=YES
use_localtime=YES
xferlog_enable=YES
connect_from_port_20=YES
chroot_local_user=YES
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
rsa_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
rsa_private_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
ssl_enable=NO
pasv_enable=YES
pasv_min_port=40000
pasv_max_port=40100
local_root=/var/www/digitaidot-kuntoon
EOF

# Käynnistä palvelu
sudo systemctl restart vsftpd
sudo systemctl enable vsftpd

# Firewall
sudo ufw allow 21/tcp
sudo ufw allow 40000:40100/tcp

echo "✅ FTP valmis! Käytä omaa käyttäjätunnustasi."
echo "🌐 Yhdistä: ftp://palvelimen-ip"
