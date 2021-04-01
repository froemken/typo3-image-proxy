# TYPO3 Extension `typo3_image_proxy`

![Build Status](https://github.com/froemken/typo3_image_proxy/workflows/CI/badge.svg)

typo3_image_proxy is an extension for TYPO3 CMS. It sends your uploaded pictures
to a hosted or containerized ImgProxy service to resize your images.

## 1 Features

* Resize your pictures while uploading in TYPO3

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require stefanfroemken/typo3-image-proxy
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `typo3_image_proxy` with the extension manager module.

### 2.2 Minimal setup

1) Open Extension Settings and configure properties to your needs
2) The uploaded files must be public available (do not block access with .htaccess or secure_download)
