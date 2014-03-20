cybs-report-parser
==================

This repository has code samples of a parsing library for parsing daily report files with cybersource and matching data against internal ordering systems data. This code is censored and missing many sections as it is designed as a sample of coding style only.

This code is Copyright (c) 2011-2014 Symantec Corporation

__Brief Description__

The cybersource daily reports are downloaded every day and their contents are matched against the order database and the results are placed in a set format for finance to download and import to their accounting system. This forms 2 of 3 independent sources for validating the financial transaction. 
The shell script is executed by a crontab each day and the output of the script is logged on the cron server and also sent by email to the reports distribution list for monitoring any errors. Exceptions are logged into a monitored database table.

This code processes report files that contain data from multiple store fronts into and sorts this data into a single financial file.

This code has also had large amounts stripped out as the code is proprietary and owned by Symantec Corporation. The code chosen reflects the full versions of the files and coding style has not been modified. Gaps in the code have comments added to reflect this.

This code reflects general standards that I always use when coding in php such as indenting control structures using 4 spaces and braces on their own lines. This code also contains many comments and explanations as other developers may review this code.
