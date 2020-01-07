#FROM ubuntu:18.04
FROM alpine:latest

LABEL maintainer="lee@leeblackwell.com"

ARG GUSER=""
ENV GUSER=$GUSER
ARG GPASS=""
ENV GPASS=$GPASS
ARG GFLDR=""
ENV GFLDR=$GFLDR
ARG FLAGS=""
ENV FLAGS=$FLAGS

COPY configure.sh /root/
COPY init.sh gmail-cleanup.php /

RUN /root/configure.sh 

# Runs "/usr/bin/dumb-init -- /my/script --with --args"
ENTRYPOINT ["/usr/bin/dumb-init", "--"]
CMD ["/init.sh"]
