# Proviso

Proviso is a tiny expression language project, usable by devs and clients, to create dynamic conditions. Its syntax is basic SQL, and supports most primitive values. Proviso has two main strengths: it's **accessible** to anyone with a basic understanding of SQL, and it's **fast** when transpiled and cached.

Proviso was part of an abandoned client project that was turning the client's ERP software into a quote builder. We had to support the dataset from the ERP that dictated a massive list of almost 100k conditions. At the time, nothing supported evaluating SQL in PHP, and translating almost 100k conditions to another expression language would take too much time.

Enter Proviso: my effort at writing a quick expression language capable of executing the client's provisos on both server and browser levels.

![](https://media1.giphy.com/media/v1.Y2lkPTc5MGI3NjExYWp1ejZwNjd4NW9hZ3c3MW1ncWRmN3JnajIxeHV4MGJxaTlmOGloZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/pqx28Oh5dZz7W/giphy.gif)

## Features

* Supports all basic SQL features required for basic rule calculations.
* Supports proxy based direct execution of expressions, in a cached and efficient manner.
  
  ```
  Proxy::execute("Foo IN ('Hello', 'World)")
  ```
* Supports transpiling from SQL expressions to JS or PHP expressions. For example, you might have an array of rule names linked to conditions to turn on/off flags, or configuration options...
  
  ```php
  // The variables used in the expression context are pulled from here.
  window.Context = {};
  window.Rules = {};
  
  <?php
  $transpiler = new JsTranspiler();
  
  foreach ($key => $condition in $domain) {
    echo "window.Rules.{$key} = ", $transpiler->transpile($condition), ";\n";
  }
  ```

## Performance

I produced the following benchmarks on the original, abandoned project. Parsing and tokenizing 352 expressions took only 69ms, with transpilation not measured but taking around 30ms.

```javascript
// Tokenize: 22.665739059448ms (352); 0.06439130414616ms each AVG
// Parsing: 46.179056167603ms (352); 0.13119050047614ms each AVG
```

And another:

```javascript
// Tokenize: 55.749654769897ms (609); 0.091542947077007ms each AVG
// Parsing: 122.92647361755ms (609); 0.20184971037365ms each AVG
```

Bear in mind that because some of these are client code, some of these expressions are impressively long.