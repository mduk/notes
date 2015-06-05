<?php

namespace Mduk\User\Service;

use Mduk\Service\Exception as ServiceException;

class Exception extends ServiceException {
  const INVALID_USER_ID = 1;
}
