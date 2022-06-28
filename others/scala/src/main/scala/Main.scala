
def developerDefinedStringsOnly(str: String with Singleton) =
  println(str)

@main def hello: Unit =
  developerDefinedStringsOnly("A")
  // developerDefinedStringsOnly("B".trim)
