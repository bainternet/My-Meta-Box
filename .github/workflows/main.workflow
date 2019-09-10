workflow "New workflow" {
  on = "push"
  resolves = ["docker://pojome/php-js-lints"]
}

action "docker://pojome/php-js-lints" {
  uses = "docker://pojome/php-js-lints"
  runs = "eslint ."
}
