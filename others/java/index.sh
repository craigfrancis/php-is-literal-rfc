
if [[ ! -d './maven/' ]]; then
	curl 'https://dlcdn.apache.org/maven/maven-3/3.8.4/binaries/apache-maven-3.8.4-bin.zip' --output 'apache-maven-3.8.4-bin.zip'
	unzip './apache-maven-3.8.4-bin.zip';
	mv './apache-maven-3.8.4' './maven'
	rm -rf 'apache-maven-3.8.4-bin.zip';
fi

./maven/bin/mvn package

cp target/isliteral-1.0.jar ./index.jar;

rm -rf target;

java -cp ./index.jar index;

#--------------------------------------------------

# ./maven/bin/mvn --version
# /usr/libexec/java_home
# JAVA_HOME="/Library/Java/JavaVirtualMachines/jdk-17.0.1.jdk/Contents/Home"

#--------------------------------------------------

# if [[ ! -d './processor/' ]]; then
# 	mkdir './processor/';
# 	curl https://repo1.maven.org/maven2/com/google/errorprone/error_prone_annotation/2.9.0/error_prone_annotation-2.9.0.jar --output ./processor/error_prone_annotation-2.9.0.jar
# 	curl https://repo1.maven.org/maven2/com/google/errorprone/error_prone_core/2.9.0/error_prone_core-2.9.0-with-dependencies.jar --output ./processor/error_prone_core-2.9.0-with-dependencies.jar
# 	curl https://repo1.maven.org/maven2/org/checkerframework/dataflow-errorprone/3.15.0/dataflow-errorprone-3.15.0.jar --output ./processor/dataflow-errorprone-3.15.0.jar
# 	curl https://repo1.maven.org/maven2/com/google/code/findbugs/jFormatString/3.0.0/jFormatString-3.0.0.jar --output ./processor/jFormatString-3.0.0.jar
# fi
#
# rm -f './index.class';
#
# javac \
#   -classpath error_prone_annotation-2.9.0.jar \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.api=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.file=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.main=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.model=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.parser=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.processing=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.tree=ALL-UNNAMED \
#   -J--add-exports=jdk.compiler/com.sun.tools.javac.util=ALL-UNNAMED \
#   -J--add-opens=jdk.compiler/com.sun.tools.javac.code=ALL-UNNAMED \
#   -J--add-opens=jdk.compiler/com.sun.tools.javac.comp=ALL-UNNAMED \
#   -XDcompilePolicy=simple \
#   -processorpath error_prone_core-2.9.0-with-dependencies.jar:dataflow-errorprone-3.15.0.jar:jFormatString-3.0.0.jar:error_prone_annotation-2.9.0.jar \
#   '-Xplugin:ErrorProne -XepDisableAllChecks -Xep:CollectionIncompatibleType:ERROR' \
#   'index.java' && java 'index';

#--------------------------------------------------