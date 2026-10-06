#!/usr/bin/env python3
"""Count tokens for tools/list measurements with the Qwen3 tokenizer.

Reads text from a file argument, or from stdin with --from-stdin, and prints
the token count on stdout. Requires the 'tokenizers' module
(pip3 install --user tokenizers); the tokenizer JSON is fetched from the
Hugging Face hub on first use and cached.

Optional companion to measure-tools-list.php; the budget gate falls back to
the byte metric when this helper cannot run.
"""
import sys

from tokenizers import Tokenizer


def main():
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    if "--from-stdin" in sys.argv:
        text = sys.stdin.read()
    elif args:
        with open(args[0], encoding="utf-8") as fh:
            text = fh.read()
    else:
        sys.exit("usage: count-tokens.py [--from-stdin] [FILE]")

    tokenizer = Tokenizer.from_pretrained("Qwen/Qwen3-8B")
    print(len(tokenizer.encode(text, add_special_tokens=False)))


if __name__ == "__main__":
    main()
