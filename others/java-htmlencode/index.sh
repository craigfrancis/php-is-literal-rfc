
if [[ ! -d './maven/' ]]; then
	curl 'https://dlcdn.apache.org/maven/maven-3/3.8.4/binaries/apache-maven-3.8.4-bin.zip' --output 'apache-maven-3.8.4-bin.zip'
	unzip './apache-maven-3.8.4-bin.zip';
	mv './apache-maven-3.8.4' './maven'
	rm -rf 'apache-maven-3.8.4-bin.zip';
fi

./maven/bin/mvn package

./maven/bin/mvn exec:java -Dexec.mainClass="index"

rm -rf target;
