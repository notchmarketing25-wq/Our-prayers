#!/usr/bin/env bash
# بيجهّز ملف zeekraa.zip نضيف (من غير config.php ولا صور مرفوعة) جاهز للرفع على الاستضافة
set -e
cd "$(dirname "$0")"
rm -f ../zeekraa.zip
zip -rq ../zeekraa.zip . -x 'config.php' 'install.lock' 'uploads/*' '.git/*' 'build-zip.sh' 'README.md'
zip -q ../zeekraa.zip uploads/.htaccess
echo "تم: $(cd .. && pwd)/zeekraa.zip"
