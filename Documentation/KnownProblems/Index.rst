.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

No images rendered on local machine
===================================

This extensions sends the public URL of your image(s) to the external
ImgProxy service. In most cases your local machine has IP 127.0.0.1
or something like 172.19.*.* if you're running your machine in a docker
or ddev container. With such IP/host it is impossible for ImgProxy service
to retrieve the file data from your local machine. Maybe it would work with
port-forwarding in your router.
