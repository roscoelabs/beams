# Beams v0.3.0
# Copyright (c) 2014-2015 Peter McKay
# Free to use under the MIT license.



FROM beams:0.3.0
RUN add-apt-repository ppa:nginx/development
RUN apt-get update && apt-get install nginx




# A couple of optional goodies for now...

# RUN curl -sL https://deb.nodesource.com/setup | sudo bash -
# RUN apt-get install nodejs
# RUN npm install forever
# RUN npm install express
# RUN apt-get install apache2-utils

