.. include:: ../Includes.txt

.. _admin-manual:

Administrator manual
====================

Installation
------------

imgproxy explains various kinds how to install its service.

Link: https://docs.imgproxy.net/#/installation

Docker in Google Cloud Run
**************************

I have created an extremely simple Dockerfile:

   FROM darthsim/imgproxy:latest

   # Google Cloud Run needs internal port 8080 by default
   EXPOSE 8080

Build the docker image:

   docker build -t imgproxy:0.0.1 .

Tag the docker image

   docker tag imgproxy:0.0.1 eu.gcr.io/[GC Project ID]/imgproxy:0.0.1

Push the image to docker container registry

Link: https://cloud.google.com/container-registry/docs/pushing-and-pulling?hl=de

   docker push eu.gcr.io/[GC Project ID]/imgproxy:0.0.1

Now you can use this docker image in Google Cloud Run.
Use the default port of 8080 as configured in Dockerfile.
Configure the concurrent requests to an instance and the min. and max. amount
of available instances. IMO RAM of 128-256 should be enough.
Use the generated public URL in Extension Setting of typo3_image_proxy.
