# Beams v0.3.0
# Copyright (c) 2014-2015 Peter McKay
# Free to use under the MIT license.




# Install all high-level components like nginx, etc...

FROM beams:0.3.0
RUN add-apt-repository ppa:nginx/development
RUN apt-get update && apt-get install nginx


# Add nginx config files...


# Install all microservices...



# Launch web_ui microservice on correct port, the one nginx script points to...




# Some optional goodies for now...

# RUN curl -sL https://deb.nodesource.com/setup | sudo bash -
# RUN apt-get install nodejs
# RUN npm install forever
# RUN npm install express
# RUN apt-get install apache2-utils


