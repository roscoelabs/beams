RUN add-apt-repository ppa:nginx/development
RUN apt-get update && apt-get install nginx
RUN curl -sL https://deb.nodesource.com/setup | sudo bash -
RUN apt-get install nodejs
RUN npm install express
RUN npm install forever
RUN apt-get install apache2-utils

