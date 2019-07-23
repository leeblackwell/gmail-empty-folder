#FROM ubuntu:18.04
FROM alpine:latest

LABEL maintainer="sysadmin@visualsoft.co.uk"

ARG GUSER=""
ENV GUSER=$GUSER
ARG GPASS=""
ENV GPASS=$GPASS
ARG GFLDR=""
ENV GFLDR=$GFLDR

COPY configure.sh configure_envcheck.00 configure_apt_keep.01 configure_php7.02 /root/

COPY init.sh gmail-cleanup.php /

RUN /root/configure.sh 

# Runs "/usr/bin/dumb-init -- /my/script --with --args"
ENTRYPOINT ["/usr/bin/dumb-init", "--"]
CMD ["/init.sh"]
