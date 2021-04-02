.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**

How to configure the extension. Try to make it easy to configure the extension.
Give a minimal example or a typical example.


Minimal Example
===============

Just install the extension and configure it over Extension Settings.


.. _configuration-extension:

Extension Settings
==================


.. _imgProxyUrl:

imgProxyUrl
-------------

Example: https://imgproxy.example.com:8090

typo3_image_proxy does not come with a pro-configured imgproxy service. It's up to you
to create/host/pay one. Enter the URI of the imgproxy service here.


.. _imgProxyKey:

imgProxyKey
-----------

Example: 0123456789abcdef0123456789abcdef

For security reasons typo3_image_proxy does NOT support resizing images with plain URLs.
Please follow section `Signing the URL` in imgproxy documentation.

Link: https://docs.imgproxy.net/#/signing_the_url


.. _imgProxySalt:

imgProxySalt
------------

Example: abcdef0123456789abcdef0123456789

For security reasons typo3_image_proxy does NOT support resizing images with plain URLs.
Please follow section `Signing the URL` in imgproxy documentation.

Link: https://docs.imgproxy.net/#/signing_the_url


.. _altLocalHostName:

altLocalHostName
----------------

Example: http://12.34.243.21:8080/

If you try to host imgproxy on same server of your website the website URL may result in
127.0.0.1 in imgproxy service or docker container. So imgproxy can not access the images from your
website. Please configure a port-forwarding or IP or another hostname to give imgproxy
a hint how to find your website.

If you host your website in a ddev container you can create a `docker-compose.override.yaml`
file with following content:

   version: '3.6'
   services:
     web:
       ports:
         - "127.0.0.1:$DDEV_HOST_WEBSERVER_PORT:80"
         - "127.0.0.1:$DDEV_HOST_HTTPS_PORT:443"
         - "[IP_OF_YOUR_SERVER]:$DDEV_HOST_WEBSERVER_PORT:80"

It's also possible to replace [IP_OF_YOUR_SERVER] with `0.0.0.0`.


.. _maxImageWidth:

maxImageWidth
-------------

Example: 1024

Configure the max. width of an image regardless if a false configuration will create an
image of 8473px width. This setting can keep the load and time low while image processing.


.. _maxImageHeight:

maxImageHeight
--------------

Example: 1024

Configure the max. height of an image regardless if a false configuration will create an
image of 4875px width. This setting can keep the load and time low while image processing.

