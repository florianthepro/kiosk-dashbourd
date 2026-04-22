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

> [!NOTE]
> usefull with [php-auth](https://github.com/florianthepro/php-auth/tree/main)(editor) and [kiosk-pi](https://github.com/florianthepro/php-auth/tree/main)

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

To setup an raspberrypi with your dashboard run
```
curl -sSL https://raw.githubusercontent.com/florianthepro/kiosk-dashbourd/refs/heads/main/setup-pi | sudo bash
```

To Set raspberrypi hdmi on/off:
```
sudo crontab -e
30 18 * * * echo off | tee /sys/class/drm/card1-HDMI-A-2/status
30 07 * * * echo on | tee /sys/class/drm/card1-HDMI-A-2/status
```
/wake:
sudo apt install -y cec-utils
