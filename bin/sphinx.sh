#!/bin/bash
#/usr/local/coreseek/bin/indexer delta --rotate >> /usr/local/coreseek/var/log/delta.log

cd /home/williamjxj/fmxw/etc/

# First I need to stop searched daemon
ps -ef | grep searchd | grep -v grep >/dev/null 2>&1
if [ $? -eq 0 ]; then
	sudo /etc/init.d/searchd stop
fi

# 2, running indexer.
sudo /usr/local/coreseek/bin/indexer --config /home/williamjxj/fmxw/etc/dixi.conf --all
#sudo /usr/local/coreseek/bin/indexer --rotate --config /home/williamjxj/fmxw/etc/dixi.conf

# 3. restart searchd.
sudo /usr/local/coreseek/bin/searchd -c /home/williamjxj/fmxw/etc/dixi.conf

# 4. check the status:
echo "Searchd Daemon running."
ps -ef | grep searched | grep -v grep
netstat -ant | grep 9313 | grep -v grep
