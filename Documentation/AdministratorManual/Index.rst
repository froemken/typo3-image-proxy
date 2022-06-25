.. include:: /Includes.rst.txt


.. _admin-manual:

====================
Administrator manual
====================

Installation
============

`imgproxy` explains various kinds how to install its service.

Link: https://docs.imgproxy.net/#/installation

Docker in Google Cloud Run
--------------------------

I have created an extremely simple Dockerfile:

.. code-block::

   FROM darthsim/imgproxy:latest

   # Set KEY/SALT as env vars in Google Cloud or by .env file, but not her in Dockerfile
   ENV IMGPROXY_KEY=[SameKeyAsConfiguredInExtensionSettings]
   ENV IMGPROXY_SALT=[SameSaltAsConfiguredInExtensionSettings]

   # PREVENT blocking imgproxy while processing big files or slow internet connection
   ENV IMGPROXY_READ_TIMEOUT=10
   ENV IMGPROXY_WRITE_TIMEOUT=10

   # max allowed mega pixel (resolution)
   ENV IMGPROXY_MAX_SRC_RESOLUTION=20.0
   ENV IMGPROXY_MAX_SRC_FILE_SIZE=134217728

   # Only resize first frame of animated images
   ENV IMGPROXY_MAX_ANIMATION_FRAMES=1

   ENV IMGPROXY_QUALITY=80
   ENV IMGPROXY_GZIP_COMPRESSION=5
   ENV IMGPROXY_USE_ETAG=true

   # These are some performance properties I have found in imgproxy docu
   ENV IMGPROXY_DOWNLOAD_BUFFER_SIZE=8388608
   ENV IMGPROXY_GZIP_BUFFER_SIZE=8388608
   ENV IMGPROXY_FREE_MEMORY_INTERVAL=30
   ENV IMGPROXY_DEVELOPMENT_ERRORS_MODE=true

   # Google Cloud Run needs internal port 8080 by default
   EXPOSE 8080

Build the docker image:

.. code-block::

   docker build -t imgproxy:0.0.1 .

Tag the docker image

.. code-block::

   docker tag imgproxy:0.0.1 eu.gcr.io/[GC Project ID]/imgproxy:0.0.1

Push the image to docker container registry

Link: https://cloud.google.com/container-registry/docs/pushing-and-pulling?hl=de

.. code-block::

   docker push eu.gcr.io/[GC Project ID]/imgproxy:0.0.1

Now you can use this docker image in Google Cloud Run.
Use the default port of 8080 as configured in Dockerfile.
Configure the concurrent requests to an instance and the min. and max. amount
of available instances. IMO RAM of 128-256 should be enough.
Use the generated public URL in Extension Setting of typo3_image_proxy.
