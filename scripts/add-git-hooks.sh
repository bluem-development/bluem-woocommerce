
#!/bin/bash

# pre-commit hook
create_pre_commit_hook() {
    echo "#!/bin/bash" > .git/hooks/pre-commit
    echo "" >> .git/hooks/pre-commit
    echo "make unit_test" >> .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
}

# pre-push hook
create_pre_push_hook() {
    echo "#!/bin/bash" > .git/hooks/pre-push
    echo "" >> .git/hooks/pre-push

    chmod +x .git/hooks/pre-push
}

# Run the script
create_pre_commit_hook
create_pre_push_hook

echo "Git hooks (pre-commit and pre-push) created successfully."
