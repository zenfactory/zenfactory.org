#!/bin/bash

#################################################################################################################################################################################################################################
#	Copyright (c) 2010, Hassan Shirani 																							#
#	All rights reserved.																								     	#
#																					     						     	#
#  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:									     	#
#																												#
#    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.												#
#    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.		#
#    * Neither the name of the Zenfactory nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.				#
#																												#
# 	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 		#
#	FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 		#
#	BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 	GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 			#
#	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.								#
#																												#
#################################################################################################################################################################################################################################

# Configuration
source /etc/p1cTools.conf

lp=$1
rp=$2

# Directory processing
function uploadDir
{
	# Recurse	
	for f in $1/*
	do
		if [ -d $f ]
		then
			uploadDir $f
		else
			headerdate=$(date +'%a, %d %b %Y %T +0000')
			hashstring=$(printf "%s\n%s\n%s\n%s\n%s\n%s" "POST" "application/octet-stream" "" "$headerdate" "`echo $rp/$f | tr A-Z a-z`" "x-emc-uid:$uid")
			signature=$(echo -n "$hashstring" | openssl dgst -binary -sha1 -hmac `echo "$secret" | base64 -d - 2> /dev/null` | base64)
			curl -v -T $f -H "Content-Type: application/octet-stream" -H "accept: */*" -H "x-emc-uid: $uid" -H "x-emc-signature: $signature" -H "Date: $headerdate" -X POST $host$rp/$f
		fi
	done
}

# Make sure paramaters were input
if [ -z $lp ]
then
	echo 'usage: ./p1cUpload.sh fileToUpload /remoteLocation (eg: ./p1cUpload myfile.txt /rest/namespace/myfiles/myfile.txt)'
	exit 1
fi

if [ -z $rp ]
then
	echo 'usage: ./p1cUpload.sh fileToUpload /remoteLocation (eg: ./p1cUpload myfile.txt /rest/namespace/myfiles/myfile.txt)'
	exit 1
fi

# Put it on the cloud
if [ -d $lp ]
then
	uploadDir $lp
else
	headerdate=$(date +'%a, %d %b %Y %T +0000')
	hashstring=$(printf "%s\n%s\n%s\n%s\n%s\n%s" "POST" "application/octet-stream" "" "$headerdate" "`echo -n $rp | tr A-Z a-z`" "x-emc-uid:$uid")
	signature=$(echo -n "$hashstring" | openssl dgst -binary -sha1 -hmac `echo "$secret" | base64 -d - 2> /dev/null` | base64)
	curl -v -T {$lp} -H "Content-Type: application/octet-stream" -H "accept: */*" -H "x-emc-uid: $uid" -H "x-emc-signature: $signature" -H "Date: $headerdate" -X POST $host$rp
fi
