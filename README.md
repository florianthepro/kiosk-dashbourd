# 📝 Description
Dashbourdtool, um  Dashbourd anzuzeigen.
<!--
<table width="100%">
  <tr valign="middle">
    <td align="left">
      <a href="https://github.com/florianthepro/csv-reporting/archive/refs/tags/v1.zip">Sourcecode</a>
    </td>
    <td align="right">
      <a href="https://github.com/florianthepro/csv-reporting/blob/README/01.md">Read →</a>
    </td>
  </tr>
</table>
-->

> [!IMPORTANT]
> pw.txt und check.php per htaccess sperren

> [!NOTE]
> pw.txt erstellen

> [!TIP]
> Apache+php:
> ```
> sudo apt update \
>&& sudo apt install -y apache2 \
>&& sudo systemctl enable --now apache2 \
>&& sudo apt install -y php libapache2-mod-php php-cli php-common php-mysql php-xml php-curl php-gd php-mbstring \
>&& sudo systemctl reload apache2
> ```
> Beschreibbar machen:
>```
>sudo usermod -aG www-data #user
>sudo chown -R #user:www-data /var/www/html/#path/
>sudo find /var/www/html/#path/ -type d -exec chmod 770 {} \;
>sudo find /var/www/html/#path/ -type f -exec chmod 660 {} \;
>```
>htaccess nutzen:
> ```
> sudo grep -q "<Directory /var/www/html>" /etc/apache2/apache2.conf || sudo tee -a /etc/apache2/apache2.conf > /dev/null <<'EOF'
><Directory /var/www/html>
>    AllowOverride All
>    Require all granted
></Directory>
>EOF
>sudo apachectl configtest && sudo systemctl reload apache2
>```
> 

To setup an raspberrypi with your dashboard run
```
curl -sSL https://raw.githubusercontent.com/florianthepro/kiosk-dashbourd/refs/heads/main/setup-pi | sudo bash
```

To Set raspberrypi hdmi on/off/wake:
```
sudo crontab -e
30 18 * * * echo off | tee /sys/class/drm/card1-HDMI-A-2/status
30 07 * * * echo on | tee /sys/class/drm/card1-HDMI-A-2/status
