Fooman Speedster Advanced
===================

### Installation Instructions
To install the extension, follow the steps in our [Installation Guide](http://magento1-support.fooman.co.nz/category/939-install-set-up-user-manual).

### Installation Options

**via composer**  
Fooman extension are included in the packages.firegento.com repository so you can install them easily via adding the extension to the require section and then running `composer install` or `composer update`

    "require":{
      "fooman/speedsteradvanced":"*"
    },

Please note that packages.firegento.com is not always up-to-date - in this case please add the following in the repositories section

    "repositories":[
      {
        "type":"composer",
        "url":"https://packages.fooman.co.nz"
      }
    ],

**via modman**  
`modman clone https://github.com/fooman/common.git`   
`modman clone https://github.com/fooman/speedsteradvanced.git`   

**via file transfer (zip download)**  
    please see the releases tab for https://github.com/fooman/speedsteradvanced/releases
    and https://github.com/fooman/common/releases
    
### Known conflicts with Fooman Speedster Advanced###
* CANONICAL URLs by Yoast - a workaround is provided in the instructions
* MXPERTS JQUERY BASE - a workaround is provided in the instructions
