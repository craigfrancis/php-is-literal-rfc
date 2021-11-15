#include <cstddef>
#include <cstdio>
#include <string>

// A comile-time only string... thanks to Jonathan Müller, https://www.jonathanmueller.dev/
//
// gcc -std=c++20 index.cpp -o index

class string_literal
{
public:

	// We use consteval to ensure that the constructor can only be called at compile-time.
	// We take as argument a reference to an array, which is the type of a string literal.

	template <std::size_t N>

	consteval string_literal(const char (&str)[N])
	: _ptr(str), _size(N - 1)
	{

	}

	constexpr const char* c_str() const {
		return _ptr;
	}

	constexpr std::size_t size() const {
		return _size;
	}

private:
	const char* _ptr;
	std::size_t _size;
};

void literal_only(string_literal str) {

	std::printf("hello %s", str.c_str());

}

int main() {

	literal_only("World"); // OK

	std::string str = "World";
	literal_only(str); // wrong type, doesn't compile

	char array[] = "World";
	array[2] = '"';
	literal_only(array); // not compile-time literal

}
