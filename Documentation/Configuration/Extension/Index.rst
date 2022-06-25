.. include:: /Includes.rst.txt


.. _extensionSettings:

==================
Extension Settings
==================


imgProxyUrl
-------------

Default: `https://imgproxy.example.com:8080`
Example: `https://imgproxy-trallala-blabla.a.run.app/`

`typo3_image_proxy` does not come with a pre-configured `imgproxy` service.
It's up to you to create/host/pay such a service. Enter the URI of the
`imgproxy` service here.


.. _imgProxyKey:

imgProxyKey
-----------

Default: Empty
Example: `0123456789abcdef0123456789abcdef`

For security reasons `typo3_image_proxy` does NOT support resizing images with
plain URLs. Please follow section `Signing the URL` in `imgproxy` documentation.

Link: https://docs.imgproxy.net/#/signing_the_url


.. _imgProxySalt:

imgProxySalt
------------

Default: Empty
Example: `abcdef0123456789abcdef0123456789`

For security reasons `typo3_image_proxy` does NOT support resizing images with
plain URLs. Please follow section `Signing the URL` in imgproxy documentation.

Link: https://docs.imgproxy.net/#/signing_the_url


.. _altLocalHostName:

altLocalHostName
----------------

Example: http://12.34.243.21:8080/

If you try to host `imgproxy` on same server of your website the website URL
may result in 127.0.0.1 in `imgproxy` service or docker container. So
`imgproxy` can not access the images from your website. Please configure a
port-forwarding or IP or another hostname to give `imgproxy` a hint how to
access your website.

If you host your website in a ddev container you can create a
`docker-compose.override.yaml` file with following content:

.. code-block::

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

Default: `1024`
Example: `1200`

Configure the max. width of an image. If an image is bigger, it will be resized
to this max. width.


.. _maxImageHeight:

maxImageHeight
--------------

Default: `1024`
Example: `1200`

Configure the max. height of an image. If an image is bigger, it will be resized
to this max. height.
