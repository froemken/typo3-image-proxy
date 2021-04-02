.. include:: ../Includes.txt


.. _introduction:

============
Introduction
============


.. _what-it-does:

What does it do?
================

With typo3_image_proxy all new and uploaded images will be sent to a an external, self-hosted or containerized
imgproxy service to resize the images. imgproxy itself is known as `fast and secure on-the-fly image processing`.
That way your server can focus on website delivery instead of processing a bunch of 8MB image files.

But!!!
======

This extension does not comes with a pre-installed imgproxy service. It is up to you to install, host, find or
pay such a service. You will find more information in section `Installation`.
As imgproxy is an external service it needs access to your files in fileadmin, uploads, ... So you should
not block public access with .htaccess or secure_downloads.
Please keep image copyright in mind. Are you allowed to sent images to foreign services?