# GitHub Guide: Branch Management and File Operations

This guide explains how to create branches, push files to GitHub, and check the status of your repository. Use these steps to effectively manage your Git workflow.

---

## **1. Creating a New Branch**

To create a new branch and switch to it:

```bash
git checkout -b <branch_name>
```

### **Example:**
```bash
git checkout -b feature/add-new-feature
```

This command creates a branch named `feature/add-new-feature` and switches to it.

To list all branches (local and remote):
```bash
git branch -a
```

---

## **2. Pushing Files to GitHub**

### **Step 1: Stage Your Changes**
Add the files you want to include in your commit:

```bash
git add <file_name>
```

To stage all changes:
```bash
git add .
```

### **Step 2: Commit Your Changes**
Save your changes with a meaningful message:

```bash
git commit -m "Your commit message here"
```

### **Step 3: Push to a Remote Repository**
Push your changes to a specific branch on GitHub:

```bash
git push -u origin <branch_name>
```

The `-u` flag sets the upstream branch, so subsequent pushes can use `git push` alone.

### **Example Workflow:**
```bash
# Add and commit changes
git add myfile.txt
git commit -m "Add myfile.txt"

# Push to the remote branch
git push -u origin feature/add-new-feature
```

---

## **3. Checking Repository Status**

To check the status of your repository:

```bash
git status
```

This command shows:
- Modified files
- Staged files
- Untracked files
- Current branch

### **Example Output:**
```plaintext
On branch feature/add-new-feature
Your branch is up to date with 'origin/feature/add-new-feature'.

Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)

        modified: myfile.txt

Untracked files:
  (use "git add <file>..." to include in what will be committed)

        newfile.txt
```

---

## **Summary Commands**

| **Action**                  | **Command**                                     |
|-----------------------------|-------------------------------------------------|
| Create a new branch         | `git checkout -b <branch_name>`                |
| Switch to an existing branch| `git checkout <branch_name>`                   |
| Add a file                  | `git add <file_name>`                          |
| Commit changes              | `git commit -m "Commit message"`              |
| Push to a remote branch     | `git push -u origin <branch_name>`             |
| Check status                | `git status`                                   |
| List branches               | `git branch -a`                                |

---

With these steps, you can efficiently manage your Git workflow. For more advanced Git operations, refer to [Git Documentation](https://git-scm.com/doc).

