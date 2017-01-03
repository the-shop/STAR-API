#!/bin/sh

cp bin/pre-commit .git/hooks/pre-commit
cp bin/post-receive .git/hooks/post-receive
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/post-receive
