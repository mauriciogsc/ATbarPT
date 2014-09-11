#!/bin/sh

#Arg 1 = text input file, Arg 2 = filename for output

echo "Starting..."

start_time=$(date +%s)

#Make the scratch directory if its not there yet.
if [ ! -d "../cache/tmp/sb_scratch" ];
        then
                echo "Temporary dir doesn't exist, creating."
        mkdir ../cache/tmp/sb_scratch
fi

#if [ $lang == "" ];
#       then
#               lang="awb"
#fi

x=0

while read fileline
do
        #For each line, process audio, appending -n for each line.

        echo "Loading line into t2w..."
        echo "$fileline" | /usr/bin/text2wave -scale 2 -o /var/www/production/server-services/speech/cache/tmp/TTS-${2}-${x}.wav #-eval "(voice_cmu_us_awb_arctic_clunits)(lex.add.entry '(\"ttssilence\" n (((pau) 0) ))"

        #cpulimit -P /usr/bin/text2wave -l 30

        interim_time=$(date +%s)

        echo "Converting to MP3, time taken so far: $((interim_time - start_time))s"
        result=`lame -f -S /var/www/production/server-services/speech/cache/tmp/TTS-${2}-${x}.wav /var/www/production/server-services/speech/cache/tmp/TTS-${2}-${x}.mp3.tmp`

        #Move the temp file to final file.
        mv /var/www/production/server-services/speech/cache/tmp/TTS-${2}-${x}.mp3.tmp /var/www/production/server-services/speech/cache/TTS-${2}-${x}.mp3

        #Remove the temporary scratch file.
        rm /var/www/production/server-services/speech/cache/tmp/TTS-${2}-${x}.wav

        #echo $fileline
        x=`expr $x + 1`
done < $1

if [ $x = 0 ]
then
        echo "Loading single line into t2w..."
        cat $1 | /usr/bin/text2wave -scale 2 -o /var/www/production/server-services/speech/cache/tmp/TTS-${2}-0.wav #-eval "(voice_cmu_us_awb_arctic_clunits)"
        echo "Converting to MP3"
        result=`lame -f -S /var/www/production/server-services/speech/cache/tmp/TTS-${2}-0.wav /var/www/production/server-services/speech/cache/tmp/TTS-${2}-0.mp3.tmp`

        mv /var/www/production/server-services/speech/cache/tmp/TTS-${2}-0.mp3.tmp /var/www/production/server-services/speech/cache/TTS-${2}-0.mp3
       # rm /var/www/production/server-services/speech/cache/tmp/TTS-${2}-0.wav
fi;

finish_time=$(date +%s)

echo "Done. Exec Time: $((finish_time - start_time))s Chunks: $x"


