#!/bin/bash
#delta.sh
#/usr/local/coreseek/bin/indexer delta --rotate >> /usr/local/coreseek/var/log/delta.log

/usr/local/coreseek/bin/indexer --rotate --config /home/williamjxj/fmxw/etc/fmxw_sphinx.conf

pid=`ps -ef | grep searchd | grep -v grep`
if [ $pid -ne 0 ]; then
	p=`netstat -ant | grep 9313 | grep -v grep`
	if [ $p  -ne 0 ]; then
		/usr/local/coreseek/bin/searchd -c /home/williamjxj/fmxw/etc/fmxw_sphinx.conf
	fi
fi
