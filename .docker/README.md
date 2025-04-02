## Publishing to the GitHub Container Registry

## Requirements

- [Docker](https://docker.com)
- [GitHub Access Token (classic)](https://github.com/settings/tokens) with read, write, delete permissions for packages

### Login

[Authenticating with a personal access token (classic)](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-with-a-personal-access-token-classic)

```shell
docker login ghcr.io -u USERNAME --password ACCESS_TOKEN
```

## Build and push

Build multi-arch images with support for ubuntu and osx.

```shell
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/daniel-de-wit/lighthouse-sanctum/php:8.2 .docker/php/8.2 --push --provenance=false
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/daniel-de-wit/lighthouse-sanctum/php:8.3 .docker/php/8.3 --push --provenance=false
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/daniel-de-wit/lighthouse-sanctum/php:8.4 .docker/php/8.4 --push --provenance=false
```

* `--provenance=false` is used to prevent the entry "unknown/unknown" to show up in OS / Arch tab.
