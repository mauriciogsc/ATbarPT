cd /var/www/production/server-services/speech/cache
echo Flushing cache...
find ./ -type f -name \*.mp3 -delete -o -name \*.txt -delete -o -name \*.xml -delete
echo done!