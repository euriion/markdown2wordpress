#!/bin/sh

for file in posts/*; do
	
	title=$(grep -i Title: $file | sed 's/Title: //')
	
	echo "file: '$file', title '$title'"
	php import.php -t "$title" -f $file -s
	break
	
done
