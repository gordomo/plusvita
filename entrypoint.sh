#!/bin/sh
env >> /etc/environment
service cron start && apache2-foreground