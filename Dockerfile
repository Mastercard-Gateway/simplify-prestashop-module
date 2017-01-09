FROM prestashop/prestashop:1.7.0.3

MAINTAINER "Simplify Commerce"

RUN mv /var/www/html/admin /var/www/html/admin-simp
CMD ["/tmp/docker_run.sh"]
