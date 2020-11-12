PHPacto MockServer
=======

Generate your [PHPacto](https://github.com/bigfoot90/phpacto) contracts from and already client-server working applications.
Simply put this recorder in the middle between your API provider and consumer.

[![License](https://poser.pugx.org/bigfoot90/phpacto/license)](https://packagist.org/packages/bigfoot90/phpacto)
[![Build Status](https://img.shields.io/travis/bigfoot90/phpacto.svg)](https://travis-ci.org/bigfoot90/phpacto)
[![CodeCov](https://img.shields.io/codecov/c/github/bigfoot90/phpacto.svg)](https://codecov.io/github/bigfoot90/phpacto)
[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/bigfoot90/phpacto.svg)](https://scrutinizer-ci.com/g/bigfoot90/phpacto)
[![Codacy Quality Grade](https://api.codacy.com/project/badge/Grade/5ca4fd2cc1044cd1923804c7a6cfc598)](https://www.codacy.com/app/bigfoot90/phpacto?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=bigfoot90/phpacto&amp;utm_campaign=Badge_Grade)
[![Latest Stable Version](https://poser.pugx.org/bigfoot90/phpacto/v/stable)](https://packagist.org/packages/bigfoot90/phpacto)
[![Total Downloads](https://poser.pugx.org/bigfoot90/phpacto/downloads)](https://packagist.org/packages/bigfoot90/phpacto)

[![Docker Build Status](https://img.shields.io/docker/build/90bigfoot/phpacto.svg)](https://hub.docker.com/r/90bigfoot/phpacto)
[![Docker Image Size](https://images.microbadger.com/badges/image/90bigfoot/phpacto.svg)](https://hub.docker.com/r/90bigfoot/phpacto)
[![Docker Pulls](https://img.shields.io/docker/pulls/90bigfoot/phpacto.svg)](https://hub.docker.com/r/90bigfoot/phpacto)
[![Docker Stars](https://img.shields.io/docker/stars/90bigfoot/phpacto.svg)](https://hub.docker.com/r/90bigfoot/phpacto)

> DISCLAIMER: PHPacto library is actually under heavy development.
> The code can be subject to any changes **without BC** until the release version `1.0.0`.
> Please use the issue tracker to report any enhancements or issues you encounter.

# Usage as standalone

```bash
export CONTRACTS_DIR='where/new-contracts/will-be-stored/'
export RECORDER_PROXY_TO='http://real.provider.api:80/'
php -S 0.0.0.0:8000 bin/mock_proxy_recorder.php
```

> SUGGESTION: Can use [phpdotenv](https://github.com/vlucas/phpdotenv) to load environment variables from file.

# Usage with Docker

```bash
docker run -it --rm \
	-v $PWD/contracts:/srv/data \
	-e CONTRACTS_DIR=data \
	-e RECORDER_PROXY_TO=http://localhost/ \
	-p 8000:8000 \
	90bigfoot/phpacto \
	mock_proxy_recorder
```
