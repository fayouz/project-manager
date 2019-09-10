FROM nginx:alpine

EXPOSE 8081:8081
COPY ./default.conf /etc/nginx/conf.d

CMD ["nginx", "-g", "daemon off;"]