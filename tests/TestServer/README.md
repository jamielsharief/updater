# Satis Server

Build the test satis server

```bash
$ cd tests/TestServer
$ docker build -t satis-dev .
```

To run the docker image

```bash
$ docker run -d -p 8000:80 satis-dev
```

You can visit the test repository

```
GET http://localhost:8000
```

There are two repos, Blockchain requires AUTH

The user name is `user` and the password `1234`


## Accessing the Docker Container

If you need to get access to the `docker` container, the container id then run `exec`

```bash
$ docker ps
$ docker exec -it a066b0872b55 /bin/bash
```

## Closing It

```bash
$ docker ps
$ docker kill 9dbf08490ba9
```