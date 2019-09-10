on: [push, pull_request]

jobs:
  phpstan:
    name: PHPStan
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@master
    - name: PHPStan
      uses: docker://oskarstark/phpstan-ga
      with:
        args: analyse src/ --level=5
 lint:
    name: Lint
    steps:
    - uses: actions/checkout@master
    - name: Lint
    - uses: docker://pojome/php-js-lint
      run:
      - eslint .
