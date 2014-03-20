#!/bin/bash

# Description
#
# This script download's and then processes the PaymentSubmissionDetailReport from Cybersource 
# and then calls the cybs_reports script to process the report file into the database 
# in a single format required by finance.
#
# Account names and file paths have been changed to protect privilaged data but still show structure.
#
# @Author Nick Mather
# @Copyright 2011-2014 Symantec Corporation

#Fetch yesterdays date and set variables for year, month and date
date=`date -d '1 day ago' +%Y-%m-%d`
year=${date:0:4}
month=${date:5:2}
day=${date:8:2}

#start processing
#Add a lock to prevent duplicate processing
touch /home/account/scripts/lock_cybs

cd /home/account/cybs

#download each of the report files
wget -O merc1_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant1/PaymentSubmissionDetailReport.csv
wget -O renew1_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant_renew1/PaymentSubmissionDetailReport.csv
wget -O merc2_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant2/PaymentSubmissionDetailReport.csv
wget -O renew2_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant_renew2/PaymentSubmissionDetailReport.csv
wget -O merc3_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant3/PaymentSubmissionDetailReport.csv
wget -O renew3_$year$month$day.csv --http-user=**** --http-password=***** https://ebc.cybersource.com/ebc/DownloadReport/$year/$month/$day/merchant_renew3/PaymentSubmissionDetailReport.csv

#Loop through the files sending them to php one by one
# While php can loop through the directory itself memory problems and segfaults have been found when 
# processing many large files and calling php seperately for each means all memory is released between 
# files. 
for file in *.csv
do
        /usr/bin/php /home/account/scripts/cybs_reports.php $file
done

#We are finished so remove the lock and then return an OK exit code
rm /home/account/scripts/lock_cybs

exit 0
