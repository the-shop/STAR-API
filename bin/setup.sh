#!/bin/sh

cp bin/pre-commit .git/hooks/pre-commit
cp bin/post-merge .git/hooks/post-merge
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/post-merge
