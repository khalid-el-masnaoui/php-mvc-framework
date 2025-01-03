FROM mysql:8.0.34-debian

SHELL ["/bin/bash", "-c"]

RUN chmod 1777 /tmp

#assign the created user same UID AND GUID OF the host for the mounted dir owner
ARG UID
ARG GID

RUN usermod -u $UID mysql
RUN groupmod -g $GID mysql

RUN rm -rf /var/lib/apt-get/lists/* /tmp/* /var/tmp/*

EXPOSE 3306

RUN chown -R mysql:mysql /var/lib/mysql

#logs
RUN install -o mysql -g mysql -d /var/log/mysql && \
    install -o mysql -g mysql /dev/null /var/log/mysql/error.log && \
    install -o mysql -g mysql /dev/null /var/log/mysql/slow.log

USER mysql
