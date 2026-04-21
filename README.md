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
> Beschreibbar machen:
>```
>sudo usermod -aG www-data #user
>sudo chown -R #user:www-data /var/www/html/#path/
>sudo find /var/www/html/#path/ -type d -exec chmod 770 {} \;
>sudo find /var/www/html/#path/ -type f -exec chmod 660 {} \;
>```

To setup an raspberrypi with your dashboard run
```
curl -sSL https://raw.githubusercontent.com/florianthepro/public/refs/heads/main/raspberrypi-monitoring/setup.sh | sudo bash
