{
  description = "Publist dev shell - PHP 8.4 + Composer matching the Docker image.";
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-24.11";
    flake-utils.url = "github:numtide/flake-utils";
  };
  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        # Keep this extension list in sync with the Dockerfile's
        # docker-php-ext-install line.
        php = pkgs.php84.withExtensions ({ enabled, all }: enabled ++ [
          all.curl all.mbstring all.intl all.pdo_mysql all.pdo_sqlite all.gd all.bcmath all.imap
        ]);
      in {
        devShells.default = pkgs.mkShell {
          packages = [ php php.packages.composer pkgs.git pkgs.mariadb ];
          shellHook = ''
            echo "PHP $(php -r 'echo PHP_VERSION;') ready - run 'composer install' once, then 'vendor/bin/tester tests/'"
            [[ $- == *i* ]] && exec zsh -i
          '';
        };
      });
}
