#!/bin/sh

cp contrib/pre-commit .git/hooks/pre-commit
cp contrib/post-receive .git/hooks/post-receive
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/post-receive
